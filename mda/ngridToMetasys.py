import csv
from datetime import datetime, date, time, timedelta
import argparse
import pandas as pd

# Convert National Grid export file to Metasys export format
def nationalGridToMetasys( ngridfile, metasysfile ):

  # Read National Grid file into a DataFrame object
  df = pd.read_csv( ngridfile, index_col=[1] )

  # Remove empty rows
  df.dropna( how='all', inplace=True )

  # Index and sort on date
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
    for ngridline in df.itertuples():

      # If we have identifying fields, process this row
      if ( not pd.isnull( ngridline[0] ) ) and ( not pd.isnull( ngridline[1] ) ) and ( not pd.isnull( ngridline[3] ) ):

        # Initialize some variables used in subsequent loop
        units = ngridline[3]
        colname = ngridline[1] + '.' + units
        sumname = colname + ".sum"

        # Loop through cells of current row
        for index in range( 3, len( ngridline ) - 1 ):

          # Get current cell
          cell = ngridline[index+1]

          # If cell is not empty, generate a Metasys row for it
          if not pd.isnull( cell ):

            # Construct a datetime object from the row index
            datesplit = ngridline[0].strftime( '%m/%d/%Y' ).split( '/' )
            dt = datetime( int( datesplit[2] ), int( datesplit[0] ), int( datesplit[1] ) )

            # Add the timestamp from the column heading to the datetime object
            timesplit = headings[index].split( ':' )
            if ( timesplit[0] == '24' ):
              dt += timedelta( days=1 )
              timesplit[0] = '00'

            dt += timedelta( hours=int( timesplit[0] ), minutes=int( timesplit[1] ) )

            # Format the timestamp for the Metasys row
            timestamp = dt.strftime( '%m/%d/%Y %H:%M' )

            # Write the Metasys row
            csvwriter.writerow( [ timestamp, '', colname, cell ] )

            # If applicable, generate artificial meter date
            if ( ( units == 'kWh' ) or ( units == 'kVAh' ) ):

              # Optionally initialize series for this fake meter
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
