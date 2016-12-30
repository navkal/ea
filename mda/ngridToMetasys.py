import csv
from datetime import datetime, date, time, timedelta
import argparse
import pandas as pd

# Convert National Grid export file to Metasys export format
def nationalGridToMetasys( ngridfile, metasysfile ):

  # Read National Grid file into a DataFrame object
  df = pd.read_csv( ngridfile )

  # Remove rows missing crucial identifying fields
  df.dropna( how='any', subset=['Account','Date','Units'], inplace=True )

  # Index and sort on date
  df.set_index( 'Date', inplace=True )
  df.index = pd.to_datetime( df.index, infer_datetime_format=True )
  df.sort_index( inplace=True )

  # Get column headings
  headings = df.columns.values

  # Open Metasys-format file and write line of headings
  with open( metasysfile, mode='w', newline="", encoding='utf-8' ) as w:
    csvwriter = csv.writer( w )
    csvwriter.writerow( ['Date / Time', 'Name Path Reference', 'Object Name', 'Object Value'] )

    # Initialize array of sums, to reconstruct meter ("summarizable") series
    sum = {}

    # Iterate through rows of National Grid data
    for ngriddate, ngridline in df.iterrows():

      # Initialize base date of current National Grid row
      datesplit = ngriddate.strftime( '%m/%d/%Y' ).split( '/' )
      basedate = datetime(int(datesplit[2]), int(datesplit[0]), int(datesplit[1]))

      # Initialize values pertaining to current National Grid row
      units = ngridline['Units']
      colname = ngridline['Account'] + '.' + units
      sumname = colname + ".sum"

      # Get series of cells from current National Grid row
      cells = ngridline[3:]
      cells.dropna( inplace=True )

      # Iterate over series of cells
      for ngridtime, cell in cells.iteritems():

        # Increment the datetime object by the timestamp shown in the column heading
        dt = basedate
        timesplit = ngridtime.split( ':' )
        if ( timesplit[0] == '24' ):
          dt += timedelta( days=1 )
          timesplit[0] = '0'
        dt += timedelta( hours=int( timesplit[0] ), minutes=int( timesplit[1] ) )

        # Format the timestamp for the Metasys row
        timestamp = dt.strftime( '%m/%d/%Y %H:%M:%S' )

        # Write the Metasys row
        csvwriter.writerow( [ timestamp, '', colname, cell ] )

        # If applicable, generate artificial meter reading
        if ( ( units == 'kWh' ) or ( units == 'kVAh' ) ):

          # Optionally initialize series for artificial meter
          if ( sumname not in sum ):
            sum[sumname] = 0

          # Increment series and write to Metasys file
          sum[sumname] += cell
          csvwriter.writerow( [ timestamp, '', sumname, sum[sumname] ] )

  return



if __name__ == "__main__":

  # Collect the command-line arguments
  parser = argparse.ArgumentParser( description='converts NG output files to metasys filetype' )
  parser.add_argument( '-i', dest='input_file',  help='name of input file' )
  parser.add_argument( '-o', dest='output_file', help='name of output file' )
  args = parser.parse_args()

  # Do the conversion
  nationalGridToMetasys( args.input_file,args.output_file )
