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

      # If this row has identifying fields, process it
      if ( not pd.isnull( ngridline[0] ) ) and ( not pd.isnull( ngridline[1] ) ) and ( not pd.isnull( ngridline[3] ) ):

        # Initialize names of series to which this row belongs
        units = ngridline[3]
        colname = ngridline[1] + '.' + units
        sumname = colname + ".sum"

        # Get base datetime for current row
        datesplit = ngridline[0].strftime('%m/%d/%Y').split('/')
        basedt = datetime(int(datesplit[2]), int(datesplit[0]), int(datesplit[1]))

        # Loop through cells of current row
        for index in range( 3, len( ngridline ) - 1 ):

          # Get current cell
          cell = ngridline[index+1]

          # If cell is not empty, generate a Metasys row for it
          if not pd.isnull( cell ):

            # Increment the datetime object by the timestamp shown in the column heading
            dt = basedt
            timesplit = headings[index].split( ':' )
            if ( timesplit[0] == '24' ):
              dt += timedelta( days=1 )
              timesplit[0] = '00'

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
  import time
  start_time = time.time()
  nationalGridToMetasys( args.input_file,args.output_file )
  print( time.time() - start_time )
